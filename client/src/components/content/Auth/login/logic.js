import { useNavigate } from "react-router-dom";
import API from "../../../../Services/axios";
import { useSelector, useDispatch } from "react-redux";
import { setField, setEmail, setPassword, setErrorMessage, clearFields } from "../../../../Features/Login/loginSlice";

export const useLoginForm = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { email, password, errorMessage } = useSelector((global) => global.login);

  const loginUser = async (e) => {
    e.preventDefault();
    dispatch(setErrorMessage(""));

    try {
      const response = await API.post("/guest/login", {
        email,
        password,
      });

      const token = response.data.payload.token;
      const user = response.data.payload;

      localStorage.setItem("token", token);
      localStorage.setItem("user", JSON.stringify(user));

      dispatch(clearFields());

      navigate("/home");
    } catch (error) {
      if (error.response) {
        dispatch(setErrorMessage(error.response.data.message || "Incorrect Email or Password"));
      } else {
        dispatch(setErrorMessage("Something went wrong. Please try again."));
      }
    }
  };

  const handleFieldChange = (field, value) => {
    dispatch(setField({ field, value }));
  };

  return {
    email,
    setEmail,
    password,
    setPassword,
    errorMessage,
    handleFieldChange,
    loginUser,
  };
};