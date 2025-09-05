import "./style.css";

const Input = ({
  name,
  type,
  required,
  hint,
  value,
  onChangeListener,
  minLength,
  maxLength,
}) => {
  return (
    <input
      className="input-style"
      name={name}
      type={type}
      required={required}
      placeholder={hint}
      value={value}
      onChange={onChangeListener}
      minLength={minLength}
      maxLength={maxLength}
    />
  );
};

export default Input;
